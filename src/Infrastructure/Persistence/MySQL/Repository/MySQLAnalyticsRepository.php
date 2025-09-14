<?php

declare(strict_types=1);

namespace Yomali\Tracker\Infrastructure\Persistence\MySQL\Repository;

use Yomali\Tracker\Domain\Analytics\Aggregate\PageAnalytics;
use Yomali\Tracker\Domain\Analytics\Repository\AnalyticsRepositoryInterface;
use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;
use Yomali\Tracker\Infrastructure\Persistence\MySQL\Connection\MySQLConnection;

final class MySQLAnalyticsRepository implements AnalyticsRepositoryInterface
{
    private const BASE_QUERY = "
        SELECT 
            page_url,
            page_domain,
            page_path,
            COUNT(DISTINCT ip_address) as unique_visits,
            COUNT(*) as total_visits
        FROM visits
    ";

    private const COUNT_PAGES_QUERY = "
        SELECT COUNT(DISTINCT CONCAT(page_domain, page_path)) as total
        FROM visits
    ";

    private \PDO $pdo;

    public function __construct(?MySQLConnection $connection = null)
    {
        $this->pdo = ($connection ?? MySQLConnection::getInstance())->getPdo();
    }

    public function getPageAnalytics(AnalyticsFilter $filter): array
    {
        [$whereClause, $params] = $this->buildWhereClause($filter);

        $sql = self::BASE_QUERY . $whereClause . "
            GROUP BY page_url, page_domain, page_path
            ORDER BY total_visits DESC, unique_visits DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map([$this, 'mapRowToPageAnalytics'], $results);
    }

    public function countPages(AnalyticsFilter $filter): int
    {
        [$whereClause, $params] = $this->buildWhereClause($filter);

        $sql = self::COUNT_PAGES_QUERY . $whereClause;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int) ($result['total'] ?? 0);
    }

    public function getPageAnalyticsByUrl(string $url, ?DateRange $dateRange = null): ?PageAnalytics
    {
        $params = [':page_url' => $url];
        $whereClause = " WHERE page_url = :page_url";

        if ($dateRange !== null) {
            $whereClause .= " AND created_at >= :start_date AND created_at <= :end_date";
            $params[':start_date'] = $dateRange->startDate->format('Y-m-d H:i:s');
            $params[':end_date'] = $dateRange->endDate->format('Y-m-d H:i:s');
        }

        $sql = self::BASE_QUERY . $whereClause . "
            GROUP BY page_url, page_domain, page_path
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ? $this->mapRowToPageAnalytics($result) : null;
    }

    public function getTopDomains(?DateRange $dateRange = null, int $limit = 10): array
    {
        $params = [':limit' => $limit];
        $whereClause = "";

        if ($dateRange !== null) {
            $whereClause = " WHERE created_at >= :start_date AND created_at <= :end_date";
            $params[':start_date'] = $dateRange->startDate->format('Y-m-d H:i:s');
            $params[':end_date'] = $dateRange->endDate->format('Y-m-d H:i:s');
        }

        $sql = "
            SELECT page_domain
            FROM visits
            $whereClause
            GROUP BY page_domain
            ORDER BY COUNT(*) DESC
            LIMIT :limit
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'page_domain');
    }

    public function getTotalStatistics(?DateRange $dateRange = null): array
    {
        $params = [];
        $whereClause = "";

        if ($dateRange !== null) {
            $whereClause = " WHERE created_at >= :start_date AND created_at <= :end_date";
            $params[':start_date'] = $dateRange->startDate->format('Y-m-d H:i:s');
            $params[':end_date'] = $dateRange->endDate->format('Y-m-d H:i:s');
        }

        $sql = "
            SELECT 
                COUNT(DISTINCT ip_address) as unique_visits,
                COUNT(*) as total_visits,
                COUNT(DISTINCT CONCAT(page_domain, page_path)) as pages
            FROM visits
            $whereClause
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'unique_visits' => (int) ($result['unique_visits'] ?? 0),
            'total_visits' => (int) ($result['total_visits'] ?? 0),
            'pages' => (int) ($result['pages'] ?? 0),
        ];
    }

    /**
     * @return array{string, array<string, mixed>}
     */
    private function buildWhereClause(AnalyticsFilter $filter): array
    {
        $conditions = [];
        $params = [];

        if ($filter->hasDateFilter()) {
            $conditions[] = "created_at >= :start_date";
            $conditions[] = "created_at <= :end_date";
            $params[':start_date'] = $filter->dateRange->startDate->format('Y-m-d H:i:s');
            $params[':end_date'] = $filter->dateRange->endDate->format('Y-m-d H:i:s');
        }

        if ($filter->hasDomainFilter()) {
            $conditions[] = "page_domain = :domain";
            $params[':domain'] = $filter->domain;
        }

        $whereClause = empty($conditions) ? "" : " WHERE " . implode(" AND ", $conditions);

        return [$whereClause, $params];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToPageAnalytics(array $row): PageAnalytics
    {
        return PageAnalytics::fromRawData(
            (string) $row['page_url'],
            (string) $row['page_domain'],
            (string) $row['page_path'],
            (int) $row['unique_visits'],
            (int) $row['total_visits']
        );
    }
}
