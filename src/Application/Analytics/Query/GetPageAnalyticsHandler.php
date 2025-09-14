<?php

declare(strict_types=1);

namespace Yomali\Tracker\Application\Analytics\Query;

use Yomali\Tracker\Application\Analytics\DTO\PageAnalyticsDTO;
use Yomali\Tracker\Domain\Analytics\Repository\AnalyticsRepositoryInterface;

final readonly class GetPageAnalyticsHandler
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository,
    ) {
    }

    /**
     * @return PageAnalyticsDTO[]
     */
    public function handle(GetPageAnalyticsQuery $query): array
    {
        $pages = $this->repository->getPageAnalytics($query->filter);

        return array_map(
            fn($page) => PageAnalyticsDTO::fromAggregate($page),
            $pages
        );
    }
}
