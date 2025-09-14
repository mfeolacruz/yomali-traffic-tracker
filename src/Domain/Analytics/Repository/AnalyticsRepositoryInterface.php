<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Analytics\Repository;

use Yomali\Tracker\Domain\Analytics\Aggregate\PageAnalytics;
use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;
use Yomali\Tracker\Domain\Analytics\ValueObject\DateRange;

interface AnalyticsRepositoryInterface
{
    /**
     * Get page analytics with optional filtering and pagination
     *
     * @param AnalyticsFilter $filter Filters to apply
     * @param int $offset Pagination offset
     * @param int $limit Maximum number of results
     * @return PageAnalytics[] Array of page analytics ordered by total visits desc
     */
    public function getPageAnalytics(
        AnalyticsFilter $filter,
        int $offset = 0,
        int $limit = 20
    ): array;

    /**
     * Count total pages matching the filters
     */
    public function countPages(AnalyticsFilter $filter): int;

    /**
     * Get analytics for a specific page URL
     */
    public function getPageAnalyticsByUrl(
        string $url,
        ?DateRange $dateRange = null
    ): ?PageAnalytics;

    /**
     * Get top domains by visit count
     *
     * @return string[] Array of domain names ordered by visit count desc
     */
    public function getTopDomains(
        ?DateRange $dateRange = null,
        int $limit = 10
    ): array;

    /**
     * Get total visit statistics across all pages
     *
     * @return array{unique_visits: int, total_visits: int, pages: int}
     */
    public function getTotalStatistics(?DateRange $dateRange = null): array;
}
