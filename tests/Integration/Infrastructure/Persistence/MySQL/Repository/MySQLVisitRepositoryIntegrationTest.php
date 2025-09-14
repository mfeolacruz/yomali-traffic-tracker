<?php

declare(strict_types=1);

namespace Yomali\Tracker\Tests\Integration\Infrastructure\Persistence\MySQL\Repository;

use Yomali\Tracker\Domain\Tracking\Aggregate\Visit;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Repository\MySQLVisitRepository;
use Yomali\Tracker\Tests\Integration\IntegrationTestCase;

/**
 * @group integration
 * @group database
 */
final class MySQLVisitRepositoryIntegrationTest extends IntegrationTestCase
{
    private MySQLVisitRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MySQLVisitRepository(MySQLConnection::getInstance());
        $this->truncateTable('visits');
    }

    public function testCanSaveVisit(): void
    {
        // Arrange
        $visit = Visit::create(
            ipAddress: '192.168.1.1',
            pageUrl: 'https://example.com/test-page'
        );

        // Act
        $this->repository->save($visit);

        // Assert
        $pdo = $this->getPdo();
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM visits");
        $result = $stmt->fetch();

        $this->assertEquals(1, $result['count']);
    }

    public function testSavedVisitHasCorrectData(): void
    {
        // Arrange
        $visit = Visit::create(
            ipAddress: '10.0.0.1',
            pageUrl: 'https://example.com/page?query=test'
        );

        // Act
        $this->repository->save($visit);

        // Assert
        $pdo = $this->getPdo();
        $stmt = $pdo->query("SELECT * FROM visits WHERE ip_address = '10.0.0.1'");
        $row = $stmt->fetch();

        $this->assertEquals('10.0.0.1', $row['ip_address']);
        $this->assertEquals('https://example.com/page?query=test', $row['page_url']);
        $this->assertEquals('example.com', $row['page_domain']);
        $this->assertEquals('/page?query=test', $row['page_path']);
    }

    public function testCanSaveMultipleVisits(): void
    {
        // Arrange & Act
        for ($i = 1; $i <= 3; $i++) {
            $visit = Visit::create("192.168.1.{$i}", "https://example.com/page{$i}");
            $this->repository->save($visit);
        }

        // Assert
        $pdo = $this->getPdo();
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM visits");
        $result = $stmt->fetch();

        $this->assertEquals(3, $result['count']);
    }
}

