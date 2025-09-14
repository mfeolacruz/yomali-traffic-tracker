<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Tracking\Infrastructure\Http\Controller;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Yomali\Tracker\Tracking\Infrastructure\Http\Controller\TrackingController;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommandHandler;
use Yomali\Tracker\Tracking\Domain\Repository\VisitRepositoryInterface;

/**
 * @group unit
 */
final class TrackingControllerTest extends TestCase
{
    private MockObject|VisitRepositoryInterface $repository;
    private TrackingController $controller;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(VisitRepositoryInterface::class);
        $handler = new TrackVisitCommandHandler($this->repository);
        $this->controller = new TrackingController($handler);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProcessTrackingWithValidData(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($visit) {
                return (string)$visit->ipAddress === '192.168.1.1'
                    && (string)$visit->url === 'https://example.com/page';
            }));

        ob_start();
        try {
            $this->controller->processTrackingRequest('{"url": "https://example.com/page"}');
            $this->assertEquals(204, http_response_code());
        } finally {
            ob_end_clean();
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProcessTrackingWithInvalidJson(): void
    {
        $this->repository->expects($this->never())->method('save');

        ob_start();
        try {
            $this->controller->processTrackingRequest('invalid json');
            $output = ob_get_contents();

            $this->assertEquals(400, http_response_code());
            $response = json_decode($output, true);
            $this->assertEquals('Invalid JSON', $response['error']);
        } finally {
            ob_end_clean();
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProcessTrackingWithMissingUrl(): void
    {
        $this->repository->expects($this->never())->method('save');

        ob_start();
        try {
            $this->controller->processTrackingRequest('{"other_field": "value"}');
            $output = ob_get_contents();

            $this->assertEquals(400, http_response_code());
            $response = json_decode($output, true);
            $this->assertEquals('URL is required', $response['error']);
        } finally {
            ob_end_clean();
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testProcessTrackingWithInvalidUrl(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $this->repository->expects($this->never())->method('save');

        ob_start();
        try {
            $this->controller->processTrackingRequest('{"url": "not-a-valid-url"}');
            $output = ob_get_contents();

            $this->assertEquals(400, http_response_code());
            $response = json_decode($output, true);
            $this->assertStringContainsString('Invalid URL', $response['error']);
        } finally {
            ob_end_clean();
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandlesOptionsRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        $this->repository->expects($this->never())->method('save');

        ob_start();
        try {
            $this->controller->track();
            $this->assertEquals(204, http_response_code());
        } finally {
            ob_end_clean();
        }
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHandlesInvalidMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->repository->expects($this->never())->method('save');

        ob_start();
        try {
            $this->controller->track();
            $output = ob_get_contents();

            $this->assertEquals(405, http_response_code());
            $response = json_decode($output, true);
            $this->assertEquals('Method not allowed', $response['error']);
        } finally {
            ob_end_clean();
        }
    }
}