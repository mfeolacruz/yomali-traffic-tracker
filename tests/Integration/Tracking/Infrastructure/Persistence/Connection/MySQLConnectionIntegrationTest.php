<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration\Tracking\Infrastructure\Persistence\Connection;

use Yomali\Tracker\Tests\Integration\IntegrationTestCase;
use Yomali\Tracker\Tracking\Infrastructure\Persistence\Connection\MySQLConnection;

/**
 * @group integration
 * @group database
 */
final class MySQLConnectionIntegrationTest extends IntegrationTestCase
{
    public function testCanConnectToDatabase(): void
    {
        $connection = MySQLConnection::getInstance();

        $this->assertTrue($connection->isConnected());
    }

    public function testCanExecuteQuery(): void
    {
        $result = $this->getPdo()->query('SELECT 1 as test')->fetch();

        $this->assertEquals(1, $result['test']);
    }

    public function testUsesCorrectDatabase(): void
    {
        $result = $this->getPdo()->query('SELECT DATABASE() as db')->fetch();

        $this->assertEquals('tracker_db_test', $result['db']);
    }

    public function testUsesUtf8mb4Charset(): void
    {
        $result = $this->getPdo()->query("SHOW VARIABLES LIKE 'character_set_connection'")->fetch();

        $this->assertEquals('utf8mb4', $result['Value']);
    }
}