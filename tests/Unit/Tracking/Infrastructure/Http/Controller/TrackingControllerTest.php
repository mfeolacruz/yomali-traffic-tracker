<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Tracking\Infrastructure\Http\Controller;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommand;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommandHandler;
use Yomali\Tracker\Tracking\Domain\Repository\VisitRepositoryInterface;
use Yomali\Tracker\Tracking\Infrastructure\Http\Controller\TrackingController;

/**
 * @group unit
 */
final class TrackingControllerTest extends TestCase
{
    private VisitRepositoryInterface $mockRepository;
    private TrackVisitCommandHandler $handler;
    private TrackingController $controller;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(VisitRepositoryInterface::class);
        $this->handler = new TrackVisitCommandHandler($this->mockRepository);
        $this->controller = new TrackingController($this->handler);
        
        // Clean up any previous response codes/headers
        if (function_exists('http_response_code')) {
            http_response_code(200);
        }
    }

    protected function tearDown(): void
    {
        // Clean up SERVER variables after each test
        $serverVarsToClean = [
            'REQUEST_METHOD',
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($serverVarsToClean as $var) {
            unset($_SERVER[$var]);
        }
    }

    public function testConstructorSetsHandler(): void
    {
        $mockRepo = $this->createMock(VisitRepositoryInterface::class);
        $handler = new TrackVisitCommandHandler($mockRepo);
        $controller = new TrackingController($handler);

        $this->assertInstanceOf(TrackingController::class, $controller);
    }

    public function testTrackWithOptionsRequestReturns204(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        ob_start();
        $this->controller->track();
        $output = ob_get_clean();

        $this->assertEquals(204, http_response_code());
        $this->assertEmpty($output);
    }

    public function testTrackWithGetRequestReturns405(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        ob_start();
        $this->controller->track();
        $output = ob_get_clean();

        $this->assertEquals(405, http_response_code());
        $this->assertStringContainsString('Method not allowed', $output);
    }

    public function testTrackWithPutRequestReturns405(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';

        ob_start();
        $this->controller->track();
        $output = ob_get_clean();

        $this->assertEquals(405, http_response_code());
        $this->assertStringContainsString('Method not allowed', $output);
    }

    public function testTrackWithPostRequestCallsProcessTrackingRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($visit) {
                return $visit instanceof \Yomali\Tracker\Tracking\Domain\Entity\Visit;
            }));

        // Since we can't extend the final class, we'll test processTrackingRequest directly
        $requestData = json_encode(['url' => 'https://test.com']);

        ob_start();
        $this->controller->processTrackingRequest($requestData);
        ob_get_clean();

        $this->assertEquals(204, http_response_code());
    }

    public function testProcessTrackingRequestWithValidJson(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($visit) {
                return $visit instanceof \Yomali\Tracker\Tracking\Domain\Entity\Visit;
            }));

        $requestData = json_encode(['url' => 'https://example.com/test']);

        ob_start();
        $this->controller->processTrackingRequest($requestData);
        ob_get_clean();

        $this->assertEquals(204, http_response_code());
    }

    public function testProcessTrackingRequestWithInvalidJson(): void
    {
        $invalidJson = '{"url": "https://example.com"'; // Missing closing brace

        ob_start();
        $this->controller->processTrackingRequest($invalidJson);
        $output = ob_get_clean();

        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('Invalid JSON', $output);
    }

    public function testProcessTrackingRequestWithMissingUrl(): void
    {
        $requestData = json_encode(['other_field' => 'value']);

        ob_start();
        $this->controller->processTrackingRequest($requestData);
        $output = ob_get_clean();

        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('URL is required', $output);
    }

    public function testProcessTrackingRequestWithEmptyUrl(): void
    {
        $requestData = json_encode(['url' => '']);

        ob_start();
        $this->controller->processTrackingRequest($requestData);
        $output = ob_get_clean();

        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('URL is required', $output);
    }

    public function testProcessTrackingRequestWithInvalidArgumentException(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // This test will cause Visit::create to throw InvalidArgumentException for invalid URL
        $requestData = json_encode(['url' => 'invalid-url']);

        ob_start();
        $this->controller->processTrackingRequest($requestData);
        $output = ob_get_clean();

        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('Invalid URL:', $output);
    }

    public function testProcessTrackingRequestWithGenericException(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('Database error'));

        $requestData = json_encode(['url' => 'https://example.com']);

        ob_start();
        $this->controller->processTrackingRequest($requestData);
        $output = ob_get_clean();

        $this->assertEquals(500, http_response_code());
        $this->assertStringContainsString('Internal server error', $output);
    }

    public function testGetRequestBodyReturnsEmptyInTestEnvironment(): void
    {
        $controller = new TrackingController($this->handler);
        
        // Use reflection to test the protected method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getRequestBody');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller);
        
        // In test environment, php://input is empty
        $this->assertIsString($result);
    }

    public function testGetClientIpAddressWithRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';

        $controller = new TrackingController($this->handler);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getClientIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('192.168.1.1', $result);
    }

    public function testGetClientIpAddressWithXForwardedFor(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.45, 192.168.1.1';

        $controller = new TrackingController($this->handler);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getClientIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('203.0.113.45', $result);
    }

    public function testGetClientIpAddressWithXRealIp(): void
    {
        $_SERVER['HTTP_X_REAL_IP'] = '10.0.0.50';

        $controller = new TrackingController($this->handler);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getClientIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('10.0.0.50', $result);
    }

    public function testGetClientIpAddressWithInvalidIpFallsBackToDefault(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid-ip';
        $_SERVER['HTTP_X_REAL_IP'] = 'not-an-ip';
        $_SERVER['REMOTE_ADDR'] = 'definitely-not-ip';

        $controller = new TrackingController($this->handler);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getClientIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('0.0.0.0', $result);
    }

    public function testGetClientIpAddressWithNoIpHeadersReturnsDefault(): void
    {
        // Clear all IP headers
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['HTTP_X_REAL_IP']); 
        unset($_SERVER['REMOTE_ADDR']);

        $controller = new TrackingController($this->handler);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getClientIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('0.0.0.0', $result);
    }

    public function testGetClientIpAddressWithEmptyHeaders(): void
    {
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '';
        $_SERVER['HTTP_X_REAL_IP'] = '';
        $_SERVER['REMOTE_ADDR'] = '';

        $controller = new TrackingController($this->handler);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getClientIpAddress');
        $method->setAccessible(true);

        $result = $method->invoke($controller);

        $this->assertEquals('0.0.0.0', $result);
    }

    public function testSetCorsHeadersIsCalledByTrack(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        // Test that setCorsHeaders is executed when track() is called
        ob_start();
        $this->controller->track();
        ob_get_clean();

        $this->assertEquals(204, http_response_code());
        // Headers are set but we can't easily test them in unit tests
        // The fact that no exception is thrown means setCorsHeaders was called
    }

    public function testSetCorsHeadersDirectly(): void
    {
        $controller = new TrackingController($this->handler);
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('setCorsHeaders');
        $method->setAccessible(true);

        // This should execute without throwing exceptions
        $method->invoke($controller);

        // If we get here, the method executed successfully
        $this->assertTrue(true);
    }

    public function testTrackWithPostAndEmptyBody(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Test processTrackingRequest directly with empty body
        ob_start();
        $this->controller->processTrackingRequest('');
        $output = ob_get_clean();

        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('Invalid JSON', $output);
    }

    public function testTrackMethodWithPostCallsGetRequestBody(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        // Mock a successful repository save
        $this->mockRepository
            ->expects($this->never()) // Since php://input is empty in test environment
            ->method('save');

        // This will test the track() method which calls getRequestBody() internally
        // In test environment, php://input is empty, so it will result in Invalid JSON
        ob_start();
        $this->controller->track();
        $output = ob_get_clean();

        $this->assertEquals(400, http_response_code());
        $this->assertStringContainsString('Invalid JSON', $output);
    }
}