<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration;

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;

/**
 * Base class for integration tests that need database
 */
abstract class IntegrationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Force test database configuration from .env
        $this->forceTestDatabaseConfig();
        
        // Ensure we're using the test database
        $this->ensureTestDatabase();
        
        // Reset singleton to ensure fresh connection with test database
        MySQLConnection::reset();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset connection for cleanup
        MySQLConnection::reset();
    }
    
    private function forceTestDatabaseConfig(): void
    {
        // Load .env to get DB_TEST_NAME
        $dotenv = Dotenv::createMutable('/var/www');
        $dotenv->load();
        
        $testDbName = $_ENV['DB_TEST_NAME'] ?? null;
        
        if (!$testDbName) {
            throw new \RuntimeException('DB_TEST_NAME environment variable is not set in .env');
        }
        
        // Override DB_NAME with test database name
        $_ENV['DB_NAME'] = $testDbName;
        putenv('DB_NAME=' . $testDbName);
        
        // Ensure we're in testing environment
        $_ENV['APP_ENV'] = 'testing';
        putenv('APP_ENV=testing');
    }
    
    private function ensureTestDatabase(): void
    {
        // Get expected test database name from .env
        $expectedTestDb = $_ENV['DB_TEST_NAME'] ?? 'tracker_db_test';
        $currentDb = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
        
        if ($currentDb !== $expectedTestDb) {
            throw new \RuntimeException(
                "DANGER: Tests are not using the test database!\n" .
                "Expected: $expectedTestDb\n" .
                "Current: $currentDb\n" .
                "This could destroy production data. Check configuration."
            );
        }
    }

    /**
     * Get a fresh PDO connection for testing
     */
    protected function getPdo(): \PDO
    {
        return MySQLConnection::getInstance()->getPdo();
    }

    /**
     * Clean a specific table
     */
    protected function truncateTable(string $table): void
    {
        $this->getPdo()->exec("TRUNCATE TABLE {$table}");
    }
}