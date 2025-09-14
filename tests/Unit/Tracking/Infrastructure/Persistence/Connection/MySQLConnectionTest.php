<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Unit\Tracking\Infrastructure\Persistence\Connection;

use PHPUnit\Framework\TestCase;
use Yomali\Tracker\Tracking\Infrastructure\Persistence\Connection\MySQLConnection;

/**
 * @group unit
 */
final class MySQLConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure we have test database config
        $_ENV['DB_HOST'] = 'mysql';
        $_ENV['DB_NAME'] = 'tracker_db_test';
        $_ENV['DB_USER'] = 'tracker_user';
        $_ENV['DB_PASSWORD'] = 'tracker_pass';
    }

    protected function tearDown(): void
    {
        // Reset singleton after each test
        MySQLConnection::reset();
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $instance1 = MySQLConnection::getInstance();
        $instance2 = MySQLConnection::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testGetPdoReturnsPdoInstance(): void
    {
        $connection = MySQLConnection::getInstance();
        $pdo = $connection->getPdo();

        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    public function testResetClearsInstance(): void
    {
        $instance1 = MySQLConnection::getInstance();
        MySQLConnection::reset();
        $instance2 = MySQLConnection::getInstance();

        $this->assertNotSame($instance1, $instance2);
    }

    public function testSingletonPatternIsImplementedCorrectly(): void
    {
        $reflection = new \ReflectionClass(MySQLConnection::class);

        // Verify __clone is private
        $cloneMethod = $reflection->getMethod('__clone');
        $this->assertTrue($cloneMethod->isPrivate());

        // Verify constructor is private
        $constructor = $reflection->getConstructor();
        $this->assertTrue($constructor->isPrivate());

        // Verify singleton instance property exists and is private
        $instanceProperty = $reflection->getProperty('instance');
        $this->assertTrue($instanceProperty->isPrivate());
        $this->assertTrue($instanceProperty->isStatic());
    }

    public function testIsConnectedReturnsTrue(): void
    {
        $connection = MySQLConnection::getInstance();

        $this->assertTrue($connection->isConnected());
    }
}