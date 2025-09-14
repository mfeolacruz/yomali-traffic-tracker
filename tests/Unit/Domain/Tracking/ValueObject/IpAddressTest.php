<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Domain\Tracking\ValueObject;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Domain\Tracking\ValueObject\IpAddress;

final class IpAddressTest extends TestCase
{
    public function testValidIpv4Address(): void
    {
        $ip = new IpAddress('192.168.1.1');
        $this->assertSame('192.168.1.1', $ip->value);
        $this->assertSame('192.168.1.1', (string) $ip);
    }

    public function testValidIpv6Address(): void
    {
        $ip = new IpAddress('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
        $this->assertSame('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $ip->value);
    }

    public function testValidIpv6Shortened(): void
    {
        $ip = new IpAddress('::1');
        $this->assertSame('::1', $ip->value);
    }

    public function testInvalidIpAddressThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP address: not.an.ip');

        new IpAddress('not.an.ip');
    }

    public function testEmptyIpAddressThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new IpAddress('');
    }

    public function testStringableInterface(): void
    {
        $ip = new IpAddress('10.0.0.1');
        $this->assertInstanceOf(\Stringable::class, $ip);
    }
}