<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Tracking\Application\Command;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommand;

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