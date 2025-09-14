<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Analytics\ValueObject;

final readonly class AnalyticsFilter
{
    public function __construct(
        public ?DateRange $dateRange = null,
        ?string $domain = null,
    ) {
        // Normalize empty string to null for consistency
        $this->domain = ($domain !== null && trim($domain) === '') ? null : $domain;
    }

    public readonly ?string $domain;

    public static function all(): self
    {
        return new self();
    }

    public static function byDomain(string $domain): self
    {
        return new self(domain: $domain);
    }

    public static function byDateRange(DateRange $dateRange): self
    {
        return new self(dateRange: $dateRange);
    }

    public static function create(?DateRange $dateRange, ?string $domain): self
    {
        return new self($dateRange, $domain);
    }

    public static function fromHttpParams(?string $startDate, ?string $endDate, ?string $domain): self
    {
        $dateRange = null;

        if (
            $startDate !== null && $endDate !== null &&
            trim($startDate) !== '' && trim($endDate) !== ''
        ) {
            try {
                $dateRange = DateRange::fromStrings($startDate, $endDate);
            } catch (\InvalidArgumentException $e) {
                // Invalid date format - ignore date filter
                $dateRange = null;
            }
        }

        return new self($dateRange, $domain);
    }

    public function hasDateFilter(): bool
    {
        return $this->dateRange !== null;
    }

    public function hasDomainFilter(): bool
    {
        return $this->domain !== null;
    }

    public function hasAnyFilter(): bool
    {
        return $this->hasDateFilter() || $this->hasDomainFilter();
    }

    public function withDateRange(DateRange $dateRange): self
    {
        return new self($dateRange, $this->domain);
    }

    public function withDomain(string $domain): self
    {
        return new self($this->dateRange, $domain);
    }

    public function withoutDateRange(): self
    {
        return new self(null, $this->domain);
    }

    public function withoutDomain(): self
    {
        return new self($this->dateRange, null);
    }
}
