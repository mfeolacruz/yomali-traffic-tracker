<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Application\Commands;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Application\Commands\TrackVisitCommand;

final class TrackVisitCommandTest extends TestCase
{
    public function testCreateCommand(): void
    {
        $command = new TrackVisitCommand(
            ipAddress: '192.168.1.1',
            pageUrl: 'https://example.com/page'
        );

        $this->assertSame('192.168.1.1', $command->ipAddress);
        $this->assertSame('https://example.com/page', $command->pageUrl);
    }
}