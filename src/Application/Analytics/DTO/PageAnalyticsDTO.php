<?php

declare(strict_types=1);

namespace Yomali\Tracker\Application\Analytics\DTO;

use Yomali\Tracker\Domain\Analytics\Aggregate\PageAnalytics;

final readonly class PageAnalyticsDTO
{
    public function __construct(
        public string $url,
        public string $domain,
        public string $path,
        public int $uniqueVisits,
        public int $totalVisits,
        public string $firstVisit,
        public string $lastVisit,
    ) {
    }

    public static function fromAggregate(PageAnalytics $pageAnalytics): self
    {
        return new self(
            $pageAnalytics->url->getValue(),
            $pageAnalytics->getDomain(),
            $pageAnalytics->getPath(),
            $pageAnalytics->getUniqueVisits(),
            $pageAnalytics->getTotalVisits(),
            $pageAnalytics->firstVisit->format('Y-m-d H:i:s'),
            $pageAnalytics->lastVisit->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'domain' => $this->domain,
            'path' => $this->path,
            'unique_visits' => $this->uniqueVisits,
            'total_visits' => $this->totalVisits,
            'first_visit' => $this->firstVisit,
            'last_visit' => $this->lastVisit,
        ];
    }
}
