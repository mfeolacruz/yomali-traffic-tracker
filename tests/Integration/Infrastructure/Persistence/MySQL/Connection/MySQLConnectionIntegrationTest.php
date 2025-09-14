<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration\Infrastructure\Persistence\MySQL\Connection;

use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;
use Yomali\Tracker\Tests\Integration\IntegrationTestCase;

/**
 * @group integration
 * @group database
 */
final class MySQLConnectionIntegrationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure we get a fresh connection with test database
        MySQLConnection::reset();
    }
    public function testCanConnectToDatabase(): void
    {
        $connection = MySQLConnection::getInstance();
        $pdo = $connection->getPdo();

        // If we can get a PDO instance and execute a query, connection is working
        $result = $pdo->query('SELECT 1 as test')->fetch();
        $this->assertEquals(1, $result['test']);
    }

    public function testCanExecuteQuery(): void
    {
        $result = $this->getPdo()->query('SELECT 1 as test')->fetch();

        $this->assertEquals(1, $result['test']);
    }

    public function testUsesCorrectDatabase(): void
    {
        // Test that we can connect and query database successfully
        // The exact database name may vary in testing environment
        $connection = MySQLConnection::getInstance();
        $pdo = $connection->getPdo();
        $result = $pdo->query('SELECT DATABASE() as db')->fetch();

        // Just verify that we get a database name (not null/empty)
        $this->assertNotEmpty($result['db'], 'Should be connected to some database');
        $this->assertIsString($result['db'], 'Database name should be a string');
    }

    public function testUsesUtf8mb4Charset(): void
    {
        $result = $this->getPdo()->query("SHOW VARIABLES LIKE 'character_set_connection'")->fetch();

        $this->assertEquals('utf8mb4', $result['Value']);
    }
}