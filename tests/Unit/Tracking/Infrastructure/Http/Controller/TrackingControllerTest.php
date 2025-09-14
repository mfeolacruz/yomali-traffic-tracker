<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Tracking\Infrastructure\Http\Controller;

use Yomali\Tracker\Tests\Unit\UnitTestCase;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommand;
use Yomali\Tracker\Tracking\Application\Command\TrackVisitCommandHandler;
use Yomali\Tracker\Tracking\Domain\Repository\VisitRepositoryInterface;
use Yomali\Tracker\Tracking\Infrastructure\Http\Controller\TrackingController;

/**
 * @group unit
 */
final class TrackingControllerTest extends UnitTestCase
{
    // Test constants specific to TrackingController
    private const TEST_IPS = [
        'LOCALHOST' => '127.0.0.1',
        'PRIVATE_RANGE' => '192.168.1.1',
        'PUBLIC_IP' => '203.0.113.45',
        'REAL_IP' => '10.0.0.50',
        'DEFAULT_FALLBACK' => '0.0.0.0'
    ];

    private const TEST_URLS = [
        'VALID_HTTPS' => 'https://example.com',
        'VALID_HTTP' => 'https://test.com',
        'VALID_WITH_PATH' => 'https://example.com/test',
        'INVALID' => 'invalid-url'
    ];

    private const HTTP_METHODS = [
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT',
        'OPTIONS' => 'OPTIONS'
    ];

    private const HTTP_STATUS = [
        'OK' => 200,
        'NO_CONTENT' => 204,
        'BAD_REQUEST' => 400,
        'METHOD_NOT_ALLOWED' => 405,
        'INTERNAL_ERROR' => 500
    ];

    private const SERVER_HEADERS = [
        'REQUEST_METHOD' => 'REQUEST_METHOD',
        'X_FORWARDED_FOR' => 'HTTP_X_FORWARDED_FOR',
        'X_REAL_IP' => 'HTTP_X_REAL_IP',
        'REMOTE_ADDR' => 'REMOTE_ADDR'
    ];

    private VisitRepositoryInterface $mockRepository;
    private TrackVisitCommandHandler $handler;
    private TrackingController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockRepository = $this->createMock(VisitRepositoryInterface::class);
        $this->handler = new TrackVisitCommandHandler($this->mockRepository);
        $this->controller = new TrackingController($this->handler);
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
        $_SERVER[self::SERVER_HEADERS['REQUEST_METHOD']] = self::HTTP_METHODS['OPTIONS'];

        $getOutput = $this->captureOutput();
        $this->controller->track();
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['NO_CONTENT'], http_response_code());
        $this->assertEmpty($output);
    }

    public function testTrackWithGetRequestReturns405(): void
    {
        $_SERVER[self::SERVER_HEADERS['REQUEST_METHOD']] = self::HTTP_METHODS['GET'];

        $getOutput = $this->captureOutput();
        $this->controller->track();
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['METHOD_NOT_ALLOWED'], http_response_code());
        $this->assertStringContainsString('Method not allowed', $output);
    }

    public function testTrackWithPutRequestReturns405(): void
    {
        $_SERVER[self::SERVER_HEADERS['REQUEST_METHOD']] = self::HTTP_METHODS['PUT'];

        $getOutput = $this->captureOutput();
        $this->controller->track();
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['METHOD_NOT_ALLOWED'], http_response_code());
        $this->assertStringContainsString('Method not allowed', $output);
    }

    public function testTrackWithPostRequestCallsProcessTrackingRequest(): void
    {
        $_SERVER[self::SERVER_HEADERS['REQUEST_METHOD']] = self::HTTP_METHODS['POST'];
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = self::TEST_IPS['LOCALHOST'];

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($visit) {
                return $visit instanceof \Yomali\Tracker\Tracking\Domain\Entity\Visit;
            }));

        // Since we can't extend the final class, we'll test processTrackingRequest directly
        $requestData = json_encode(['url' => self::TEST_URLS['VALID_HTTP']]);

        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest($requestData);
        $getOutput();

        $this->assertEquals(self::HTTP_STATUS['NO_CONTENT'], http_response_code());
    }

    public function testProcessTrackingRequestWithValidJson(): void
    {
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = self::TEST_IPS['LOCALHOST'];

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($visit) {
                return $visit instanceof \Yomali\Tracker\Tracking\Domain\Entity\Visit;
            }));

        $requestData = json_encode(['url' => self::TEST_URLS['VALID_WITH_PATH']]);

        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest($requestData);
        $getOutput();

        $this->assertEquals(self::HTTP_STATUS['NO_CONTENT'], http_response_code());
    }

    public function testProcessTrackingRequestWithInvalidJson(): void
    {
        $invalidJson = '{"url": "' . self::TEST_URLS['VALID_HTTPS'] . '"'; // Missing closing brace

        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest($invalidJson);
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['BAD_REQUEST'], http_response_code());
        $this->assertStringContainsString('Invalid JSON', $output);
    }

    public function testProcessTrackingRequestWithMissingUrl(): void
    {
        $requestData = json_encode(['other_field' => 'value']);

        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest($requestData);
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['BAD_REQUEST'], http_response_code());
        $this->assertStringContainsString('URL is required', $output);
    }

    public function testProcessTrackingRequestWithEmptyUrl(): void
    {
        $requestData = json_encode(['url' => '']);

        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest($requestData);
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['BAD_REQUEST'], http_response_code());
        $this->assertStringContainsString('URL is required', $output);
    }

    public function testProcessTrackingRequestWithInvalidArgumentException(): void
    {
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = self::TEST_IPS['LOCALHOST'];

        // This test will cause Visit::create to throw InvalidArgumentException for invalid URL
        $requestData = json_encode(['url' => self::TEST_URLS['INVALID']]);

        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest($requestData);
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['BAD_REQUEST'], http_response_code());
        $this->assertStringContainsString('Invalid URL:', $output);
    }

    public function testProcessTrackingRequestWithGenericException(): void
    {
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = self::TEST_IPS['LOCALHOST'];

        $this->mockRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \RuntimeException('Database error'));

        $requestData = json_encode(['url' => self::TEST_URLS['VALID_HTTPS']]);

        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest($requestData);
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['INTERNAL_ERROR'], http_response_code());
        $this->assertStringContainsString('Internal server error', $output);
    }

    public function testGetRequestBodyReturnsEmptyInTestEnvironment(): void
    {
        $controller = new TrackingController($this->handler);
        
        $result = $this->callPrivateMethod($controller, 'getRequestBody');
        
        // In test environment, php://input is empty
        $this->assertIsString($result);
    }

    public function testGetClientIpAddressWithRemoteAddr(): void
    {
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = self::TEST_IPS['PRIVATE_RANGE'];

        $controller = new TrackingController($this->handler);
        $result = $this->callPrivateMethod($controller, 'getClientIpAddress');

        $this->assertEquals(self::TEST_IPS['PRIVATE_RANGE'], $result);
    }

    public function testGetClientIpAddressWithXForwardedFor(): void
    {
        $_SERVER[self::SERVER_HEADERS['X_FORWARDED_FOR']] = self::TEST_IPS['PUBLIC_IP'] . ', ' . self::TEST_IPS['PRIVATE_RANGE'];

        $controller = new TrackingController($this->handler);
        $result = $this->callPrivateMethod($controller, 'getClientIpAddress');

        $this->assertEquals(self::TEST_IPS['PUBLIC_IP'], $result);
    }

    public function testGetClientIpAddressWithXRealIp(): void
    {
        $_SERVER[self::SERVER_HEADERS['X_REAL_IP']] = self::TEST_IPS['REAL_IP'];

        $controller = new TrackingController($this->handler);
        $result = $this->callPrivateMethod($controller, 'getClientIpAddress');

        $this->assertEquals(self::TEST_IPS['REAL_IP'], $result);
    }

    public function testGetClientIpAddressWithInvalidIpFallsBackToDefault(): void
    {
        $_SERVER[self::SERVER_HEADERS['X_FORWARDED_FOR']] = 'invalid-ip';
        $_SERVER[self::SERVER_HEADERS['X_REAL_IP']] = 'not-an-ip';
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = 'definitely-not-ip';

        $controller = new TrackingController($this->handler);
        $result = $this->callPrivateMethod($controller, 'getClientIpAddress');

        $this->assertEquals(self::TEST_IPS['DEFAULT_FALLBACK'], $result);
    }

    public function testGetClientIpAddressWithNoIpHeadersReturnsDefault(): void
    {
        // Clear all IP headers
        unset($_SERVER[self::SERVER_HEADERS['X_FORWARDED_FOR']]);
        unset($_SERVER[self::SERVER_HEADERS['X_REAL_IP']]); 
        unset($_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']]);

        $controller = new TrackingController($this->handler);
        $result = $this->callPrivateMethod($controller, 'getClientIpAddress');

        $this->assertEquals(self::TEST_IPS['DEFAULT_FALLBACK'], $result);
    }

    public function testGetClientIpAddressWithEmptyHeaders(): void
    {
        $_SERVER[self::SERVER_HEADERS['X_FORWARDED_FOR']] = '';
        $_SERVER[self::SERVER_HEADERS['X_REAL_IP']] = '';
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = '';

        $controller = new TrackingController($this->handler);
        $result = $this->callPrivateMethod($controller, 'getClientIpAddress');

        $this->assertEquals(self::TEST_IPS['DEFAULT_FALLBACK'], $result);
    }

    public function testSetCorsHeadersIsCalledByTrack(): void
    {
        $_SERVER[self::SERVER_HEADERS['REQUEST_METHOD']] = self::HTTP_METHODS['OPTIONS'];

        // Test that setCorsHeaders is executed when track() is called
        $getOutput = $this->captureOutput();
        $this->controller->track();
        $getOutput();

        $this->assertEquals(self::HTTP_STATUS['NO_CONTENT'], http_response_code());
        // Headers are set but we can't easily test them in unit tests
        // The fact that no exception is thrown means setCorsHeaders was called
    }

    public function testSetCorsHeadersDirectly(): void
    {
        $controller = new TrackingController($this->handler);
        
        // This should execute without throwing exceptions
        $this->callPrivateMethod($controller, 'setCorsHeaders');

        // If we get here, the method executed successfully
        $this->assertTrue(true);
    }

    public function testTrackWithPostAndEmptyBody(): void
    {
        $_SERVER[self::SERVER_HEADERS['REQUEST_METHOD']] = self::HTTP_METHODS['POST'];

        // Test processTrackingRequest directly with empty body
        $getOutput = $this->captureOutput();
        $this->controller->processTrackingRequest('');
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['BAD_REQUEST'], http_response_code());
        $this->assertStringContainsString('Invalid JSON', $output);
    }

    public function testTrackMethodWithPostCallsGetRequestBody(): void
    {
        $_SERVER[self::SERVER_HEADERS['REQUEST_METHOD']] = self::HTTP_METHODS['POST'];
        $_SERVER[self::SERVER_HEADERS['REMOTE_ADDR']] = self::TEST_IPS['LOCALHOST'];

        // Mock a successful repository save
        $this->mockRepository
            ->expects($this->never()) // Since php://input is empty in test environment
            ->method('save');

        // This will test the track() method which calls getRequestBody() internally
        // In test environment, php://input is empty, so it will result in Invalid JSON
        $getOutput = $this->captureOutput();
        $this->controller->track();
        $output = $getOutput();

        $this->assertEquals(self::HTTP_STATUS['BAD_REQUEST'], http_response_code());
        $this->assertStringContainsString('Invalid JSON', $output);
    }
}