<?php

declare(strict_types=1);

namespace Yomali\Tracker\Infrastructure\Persistence\MySQL\Repository;

use Yomali\Tracker\Domain\Tracking\Aggregate\Visit;
use Yomali\Tracker\Domain\Tracking\Repository\VisitRepositoryInterface;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;

/**
 * MySQL implementation of VisitRepositoryInterface
 */
final class MySQLVisitRepository implements VisitRepositoryInterface
{
    private const INSERT_VISIT = "
        INSERT INTO visits (
            ip_address,
            page_url,
            page_domain,
            page_path,
            created_at
        ) VALUES (
            :ip_address,
            :page_url,
            :page_domain,
            :page_path,
            :created_at
        )
    ";

    private \PDO $pdo;

    public function __construct(?MySQLConnection $connection = null)
    {
        $this->pdo = ($connection ?? MySQLConnection::getInstance())->getPdo();
    }

    /**
     * @inheritDoc
     */
    public function save(Visit $visit): void
    {
        $stmt = $this->pdo->prepare(self::INSERT_VISIT);
        $stmt->execute([
            ':ip_address' => (string)$visit->ipAddress,
            ':page_url' => (string)$visit->url,
            ':page_domain' => $visit->url->domain,
            ':page_path' => $visit->url->path,
            ':created_at' => $visit->createdAt->format('Y-m-d H:i:s')
        ]);
    }
}
