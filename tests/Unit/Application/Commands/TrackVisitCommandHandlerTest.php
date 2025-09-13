<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Application\Commands;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Yomali\Tracker\Application\Commands\TrackVisitCommand;
use Yomali\Tracker\Application\Commands\TrackVisitCommandHandler;
use Yomali\Tracker\Domain\Visit\Visit;
use Yomali\Tracker\Domain\Visit\VisitRepositoryInterface;

final class TrackVisitCommandHandlerTest extends TestCase
{
    private MockObject|VisitRepositoryInterface $repository;
    private TrackVisitCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VisitRepositoryInterface::class);
        $this->handler = new TrackVisitCommandHandler($this->repository);
    }

    public function testHandleValidCommand(): void
    {
        $command = new TrackVisitCommand(
            ipAddress: '192.168.1.1',
            pageUrl: 'https://example.com/test'
        );

        // Expect the repository save method to be called once with a Visit object
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) {
                return (string) $visit->ipAddress === '192.168.1.1'
                    && (string) $visit->url === 'https://example.com/test';
            }));

        $this->handler->handle($command);
    }

    public function testHandleInvalidIpThrowsException(): void
    {
        $command = new TrackVisitCommand(
            ipAddress: 'invalid.ip',
            pageUrl: 'https://example.com'
        );

        $this->repository->expects($this->never())->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid IP address');

        $this->handler->handle($command);
    }

    public function testHandleInvalidUrlThrowsException(): void
    {
        $command = new TrackVisitCommand(
            ipAddress: '192.168.1.1',
            pageUrl: 'not a url'
        );

        $this->repository->expects($this->never())->method('save');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL');

        $this->handler->handle($command);
    }

    public function testHandleMultipleCommands(): void
    {
        $commands = [
            new TrackVisitCommand('192.168.1.1', 'https://example.com/page1'),
            new TrackVisitCommand('10.0.0.1', 'https://example.com/page2'),
            new TrackVisitCommand('::1', 'https://example.com/page3'),
        ];

        $this->repository
            ->expects($this->exactly(3))
            ->method('save');

        foreach ($commands as $command) {
            $this->handler->handle($command);
        }
    }
}