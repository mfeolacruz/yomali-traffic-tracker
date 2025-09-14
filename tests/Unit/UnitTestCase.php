<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Dotenv\Dotenv;

/**
 * Base class for unit tests that provides common functionality
 */
abstract class UnitTestCase extends TestCase
{
    private static bool $envLoaded = false;


    protected function setUp(): void
    {
        parent::setUp();
        $this->loadEnvironmentOnce();
        $this->resetHttpState();
    }

    protected function tearDown(): void
    {
        $this->cleanupServerVars();
        parent::tearDown();
    }

    /**
     * Load environment variables once for all tests
     */
    private function loadEnvironmentOnce(): void
    {
        if (self::$envLoaded) {
            return;
        }

        $dotenv = Dotenv::createMutable('/var/www');
        $dotenv->safeLoad();
        self::$envLoaded = true;
    }

    /**
     * Reset HTTP response code to default state
     */
    protected function resetHttpState(): void
    {
        if (function_exists('http_response_code')) {
            http_response_code(200);
        }
    }

    /**
     * Clean up SERVER variables that may affect other tests
     */
    protected function cleanupServerVars(): void
    {
        $varsToClean = [
            'REQUEST_METHOD',
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($varsToClean as $var) {
            unset($_SERVER[$var]);
        }
    }

    /**
     * Helper to call private/protected methods via reflection
     */
    protected function callPrivateMethod(object $object, string $methodName, ...$args): mixed
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invoke($object, ...$args);
    }

    /**
     * Helper to get environment variable with optional default
     */
    protected function getEnv(string $key, ?string $default = null): ?string
    {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Set up test database configuration from environment
     */
    protected function setTestDbConfig(): void
    {
        $requiredEnvVars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD'];
        $testNameVar = 'DB_TEST_NAME';
        $portVar = 'DB_PORT';
        
        // Ensure required variables exist
        foreach ($requiredEnvVars as $var) {
            if (!$this->getEnv($var)) {
                throw new \RuntimeException("Required environment variable '{$var}' is not set");
            }
            $_ENV[$var] = $this->getEnv($var);
        }
        
        // Set test database name
        $testDbName = $this->getEnv($testNameVar);
        if (!$testDbName) {
            throw new \RuntimeException("Required environment variable '{$testNameVar}' is not set");
        }
        $_ENV['DB_NAME'] = $testDbName;
        
        // Port is optional, use standard MySQL port if not set
        $_ENV['DB_PORT'] = $this->getEnv($portVar) ?: '3306';
    }

    /**
     * Start output buffering and return a cleanup closure
     */
    protected function captureOutput(): \Closure
    {
        ob_start();
        
        return function (): string {
            return ob_get_clean() ?: '';
        };
    }
}