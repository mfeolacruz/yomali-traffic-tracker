<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Tracking\Infrastructure\Persistence\Connection\MySQLConnection;
use Dotenv\Dotenv;

/**
 * Base class for integration tests that need database
 */
abstract class IntegrationTestCase extends TestCase
{
    private static bool $envLoaded = false;
    private static string $originalDbName = '';
    private static string $testDbName = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Load .env once
        if (!self::$envLoaded) {
            $dotenv = Dotenv::createMutable('/var/www');
            $dotenv->load();

            // Store original and test database names
            self::$originalDbName = $_ENV['DB_NAME'] ?? 'tracker_db';
            self::$testDbName = $_ENV['DB_TEST_NAME'] ?? 'tracker_db_test';
            self::$envLoaded = true;
        }

        // Reset singleton BEFORE changing environment
        MySQLConnection::reset();

        // Switch to test database
        $_ENV['DB_NAME'] = self::$testDbName;
        putenv('DB_NAME=' . self::$testDbName);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Reset connection
        MySQLConnection::reset();

        // Restore original database
        $_ENV['DB_NAME'] = self::$originalDbName;
        putenv('DB_NAME=' . self::$originalDbName);
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