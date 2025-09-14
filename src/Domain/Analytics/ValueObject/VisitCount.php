<?php

declare(strict_types=1);

namespace Yomali\Tracker\Domain\Analytics\ValueObject;

final readonly class VisitCount
{
    public function __construct(
        public int $uniqueVisits,
        public int $totalVisits,
    ) {
        if ($uniqueVisits < 0) {
            throw new \InvalidArgumentException('Unique visits cannot be negative');
        }

        if ($totalVisits < 0) {
            throw new \InvalidArgumentException('Total visits cannot be negative');
        }

        if ($uniqueVisits > $totalVisits) {
            throw new \InvalidArgumentException('Unique visits cannot exceed total visits');
        }
    }

    public static function zero(): self
    {
        return new self(0, 0);
    }

    public static function fromTotals(int $unique, int $total): self
    {
        return new self($unique, $total);
    }

    public function add(self $other): self
    {
        return new self(
            $this->uniqueVisits + $other->uniqueVisits,
            $this->totalVisits + $other->totalVisits
        );
    }

    public function hasVisits(): bool
    {
        return $this->totalVisits > 0;
    }

    public function getUniqueRatio(): float
    {
        if ($this->totalVisits === 0) {
            return 0.0;
        }

        return $this->uniqueVisits / $this->totalVisits;
    }

    public function equals(self $other): bool
    {
        return $this->uniqueVisits === $other->uniqueVisits
            && $this->totalVisits === $other->totalVisits;
    }
}
