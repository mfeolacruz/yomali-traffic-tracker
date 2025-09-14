<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Analytics\Aggregate;

use Yomali\Tracker\Domain\Analytics\ValueObject\VisitCount;
use Yomali\Tracker\Domain\Analytics\ValueObject\Url;

final readonly class PageAnalytics
{
    public function __construct(
        public Url $url,
        public VisitCount $visitCount,
    ) {
    }

    public static function create(
        Url $url,
        int $uniqueVisits,
        int $totalVisits
    ): self {
        return new self(
            $url,
            VisitCount::fromTotals($uniqueVisits, $totalVisits)
        );
    }

    public static function fromRawData(
        string $url,
        string $domain,
        string $path,
        int $uniqueVisits,
        int $totalVisits
    ): self {
        return new self(
            Url::create($url, $domain, $path),
            VisitCount::fromTotals($uniqueVisits, $totalVisits)
        );
    }

    public function getDomain(): string
    {
        return $this->url->getDomain();
    }

    public function getPath(): string
    {
        return $this->url->getPath();
    }

    public function getUniqueVisits(): int
    {
        return $this->visitCount->uniqueVisits;
    }

    public function getTotalVisits(): int
    {
        return $this->visitCount->totalVisits;
    }

    public function hasTraffic(): bool
    {
        return $this->visitCount->hasVisits();
    }

    public function getUniqueRatio(): float
    {
        return $this->visitCount->getUniqueRatio();
    }

    public function isSamePage(Url $url): bool
    {
        return $this->url->equals($url);
    }

    public function merge(self $other): self
    {
        if (!$this->isSamePage($other->url)) {
            throw new \InvalidArgumentException('Cannot merge analytics for different pages');
        }

        return new self(
            $this->url,
            $this->visitCount->add($other->visitCount)
        );
    }

    public function equals(self $other): bool
    {
        return $this->url->equals($other->url)
            && $this->visitCount->equals($other->visitCount);
    }
}
