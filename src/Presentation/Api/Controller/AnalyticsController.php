<?php

declare(strict_types=1);

namespace Yomali\Tracker\Presentation\Api\Controller;

use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsHandler;
use Yomali\Tracker\Application\Analytics\Query\GetPageAnalyticsQuery;
use Yomali\Tracker\Domain\Analytics\ValueObject\AnalyticsFilter;

final class AnalyticsController
{
    public function __construct(
        private readonly GetPageAnalyticsHandler $getPageAnalyticsHandler
    ) {
    }

    public function getPageAnalytics(): void
    {
        $this->setCorsHeaders();

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Allow: GET, OPTIONS');
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $this->processGetPageAnalyticsRequest();
    }

    public function processGetPageAnalyticsRequest(): void
    {
        try {
            $params = $this->getQueryParams();
            $filter = $this->createAnalyticsFilter($params);
            $query = new GetPageAnalyticsQuery($filter);

            $pageAnalyticsDTOs = $this->getPageAnalyticsHandler->handle($query);

            $paginatedData = $this->applyPagination($pageAnalyticsDTOs, $params);

            http_response_code(200);
            echo json_encode($paginatedData);
        } catch (\InvalidArgumentException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getQueryParams(): array
    {
        return [
            'start_date' => $_GET['start_date'] ?? null,
            'end_date' => $_GET['end_date'] ?? null,
            'domain' => $_GET['domain'] ?? null,
            'page' => (int) ($_GET['page'] ?? 1),
            'limit' => (int) ($_GET['limit'] ?? 20),
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function createAnalyticsFilter(array $params): AnalyticsFilter
    {
        return AnalyticsFilter::fromHttpParams(
            $params['start_date'],
            $params['end_date'],
            $params['domain']
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function validatePaginationParams(array $params): void
    {
        if ($params['page'] < 1) {
            throw new \InvalidArgumentException('Page must be at least 1');
        }

        if ($params['limit'] < 1 || $params['limit'] > 100) {
            throw new \InvalidArgumentException('Limit must be between 1 and 100');
        }
    }

    /**
     * @param array<mixed> $items
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function applyPagination(array $items, array $params): array
    {
        $this->validatePaginationParams($params);

        $page = $params['page'];
        $limit = $params['limit'];
        $total = count($items);
        $offset = ($page - 1) * $limit;

        $paginatedItems = array_slice($items, $offset, $limit);
        $totalPages = (int) ceil($total / $limit);

        return [
            'data' => array_map(fn($dto) => $dto->toArray(), $paginatedItems),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1,
            ],
        ];
    }

    private function setCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 86400');
        header('Content-Type: application/json');
    }
}
